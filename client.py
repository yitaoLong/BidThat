import socket
import sys
import time
import random


class Client():
    def __init__(self, port=5000):
        self.socket = socket.socket()
        self.port = port

        self.socket.connect(("localhost", port))

        # Send over the team name
        self.socket.send("Python Client".encode("utf-8"))

        # Wait to get the ready message, which includes whether we are player 1 or player 2
        # and the initial number of stones in the form of a string "{p_num} {num_stones}"
        # This is important for calculating the opponent's move in the case where we go second
        init_info = [int(x) for x in self.socket.recv(1024).decode().rstrip().split(" ")]

        self.items = init_info[:-2]
        self.team_id = init_info[-1]
        self.budget = init_info[-2]

        print(f'Your are team {self.team_id}.')
        print(f'The starting budget is {self.budget}.')
        print(f'The value of items in auction are {self.items}.')

    def getstate(self):
        '''
        Query the server for the current state of the game and wait until a response is received
        before returning
        '''

        # Send the request
        # self.socket.send("getstate".encode("utf-8"))

        # Wait for the response (hangs here until response is received from server)
        return self.socket.recv(1024).decode().rstrip().split(" ")

    def sendbid(self, bid):
        '''
        Send a bid to the server for current auction item. The server does not send a response / acknowledgement,
        so a call to getstate() afterwards is necessary in order to wait until the next move
        '''

        self.socket.send(f"{bid}".encode("utf-8"))

    def generatebid(self, item_ind, bids):
        '''
        Given the state of the game as input, computes the desired move and returns it.
        NOTE: this is just one way to handle the agent's policy -- feel free to add other
          features or data structures as you see fit, as long as playgame() still runs!
        '''

        raise NotImplementedError

    def processresult(self, result):
        '''
        Given the state of the game as input, computes the desired move and returns it.
        NOTE: this is just one way to handle the agent's policy -- feel free to add other
          features or data structures as you see fit, as long as playgame() still runs!
        '''

        raise NotImplementedError

    def playgame(self):
        '''
        Plays through a game of Card Nim from start to finish by looping calls to getstate(),
        generatebid(), and sendbid() in that order
        '''


        item_ind = 1
        bid = [self.generatefirstbid(item_ind, []), self.team_id]
        self.sendbid(" ".join([str(i) for i in bid]))

        while True:
            state = self.getstate()
            bids = []
            if state[0] == "bid":
                bids = [int(x) for x in state[1:]]
            elif state[0] == "result":
                item_ind += 1
            elif state[0] == "end":
                break;

            bid = [self.generatebid(item_ind, bids), self.team_id]
            self.sendbid(" ".join([str(i) for i in bid]))

            time.sleep(0.1)

        self.socket.close()


class NaivePlayer(Client):
    '''
    Very simple client which just starts at the lowest possible move
    and increases its move by 1 each turn
    '''

    def __init__(self, port=5000):
        super(NaivePlayer, self).__init__(port)

    def generatebid(self, item_ind, bids):
        return random.randint(0, self.budget)

    def processresult(self, result):
        return

class MyPlayer(Client):
    '''
    Your custom solver!
    '''

    def __init__(self, port=5000):
        super(NaivePlayer, self).__init__(port)

    def generatebid(self, state):
        '''
        TODO: put your solver logic here!
        '''
        return None


if __name__ == '__main__':
    if len(sys.argv) == 1:
        port = 5000
    else:
        port = int(sys.argv[1])

    # Change NaivePlayer(port) to MyPlayer(port) to use your custom solver
    client = NaivePlayer(port)
    client.playgame()
